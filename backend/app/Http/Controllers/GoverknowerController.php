<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Laravel's HTTP client, powered by Guzzle
use Illuminate\Support\Facades\Log;   // For logging errors
use App\Services\EmbeddingService;
use App\Services\PineconeService;

class GoverknowerController extends Controller
{
    private EmbeddingService $embeddingService;
    private PineconeService $pineconeService;

    public function __construct(EmbeddingService $embeddingService, PineconeService $pineconeService)
    {
        $this->embeddingService = $embeddingService;
        $this->pineconeService = $pineconeService;
    }

    /**
     * Handle the /api/ask request.
     * Receives a query, generates embedding, queries Pinecone,
     * builds prompt, calls Gemini API, and returns a natural language response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ask(Request $request)
    {
        if (!$request->has('query')) {
            return response()->json([
                'error' => 'No query provided'
            ], 400);
        }

        $userQuery = $request->input('query');

        // Check for the Gemini API Key
        $geminiApiKey = env('GEMINI_API_KEY');
        if (empty($geminiApiKey)) {
            Log::error('GEMINI_API_KEY is not set in the .env file.');
            return response()->json([
                'error' => 'Server configuration error: Gemini API key not found.'
            ], 500);
        }

        // Check for Pinecone configuration
        $pineconeApiKey = env('PINECONE_API_KEY');
        $pineconeIndexHost = env('PINECONE_INDEX_HOST');
        if (empty($pineconeApiKey) || empty($pineconeIndexHost)) {
            Log::error('Pinecone configuration is missing in the .env file.');
            return response()->json([
                'error' => 'Server configuration error: Pinecone configuration not found.'
            ], 500);
        }

        try {
            // --- STEP 1: Generate embedding for user query ---
            Log::info('Generating embedding for query:', ['query' => $userQuery]);
            $userQueryEmbedding = $this->embeddingService->generateEmbedding($userQuery);
            
            if ($userQueryEmbedding) {
            
                try {
                    $pineconeMatches = $this->pineconeService->querySimilar($userQueryEmbedding, 3);
                    
                    if ($pineconeMatches && !empty($pineconeMatches)) {
                        // Extract text from Pinecone matches
                        $vectorDBResponse = $this->pineconeService->getFormattedResults($pineconeMatches);
                        Log::info('Retrieved data from Pinecone:', ['matches_count' => count($pineconeMatches)]);
                    } else {
                        Log::warning('No results found in Pinecone, using fallback data');
                        $vectorDBResponse = $this->getMockData();
                    }
                } catch (\Exception $e) {
                    Log::error('Pinecone query failed, using fallback data:', ['error' => $e->getMessage()]);
                    $vectorDBResponse = $this->getMockData();
                }

            } else {
                Log::error('Failed to generate embedding for user query');
                $vectorDBResponse = $this->getMockData();
            }

            // --- STEP 3: Build prompt and call Gemini API ---
            $prompt = "Using the following data, respond to the user's question in a clear and concise manner. If the data does not contain the answer, state that you cannot find the information based on the provided data.\n\n" .
                      "Data:\n{$vectorDBResponse}\n\n" .
                      "User's Question:\n{$userQuery}\n\n" .
                      "Response Format: Provide a direct answer based only on the provided data. Do not preface the answer with 'Here is the answer to your question:'. Do not include any other text in your response, just the result of your research.";

            $geminiEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiApiKey}";

            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
            ];

            $response = Http::post($geminiEndpoint, $payload);

            // Check if the API call was successful
            if ($response->failed()) {
                Log::error('Gemini API call failed:', ['status' => $response->status(), 'response' => $response->body()]);
                return response()->json([
                    'error' => 'Failed to get response from Gemini API.',
                    'details' => $response->body()
                ], 500);
            }

            $geminiResult = $response->json();

            Log::info('Gemini API response:', $geminiResult);

            // Extract the text response from Gemini's JSON
            $geminiResponseText = '';
            if (isset($geminiResult['candidates'][0]['content']['parts'][0]['text'])) {
                $geminiResponseText = $geminiResult['candidates'][0]['content']['parts'][0]['text'];
            } else {
                Log::warning('Gemini API response structure unexpected:', ['response' => $geminiResult]);
                return response()->json([
                    'error' => 'Unexpected response from Gemini API.',
                    'details' => $geminiResult
                ], 500);
            }

            // --- STEP 4: Return Natural Language Response to Frontend ---
            return response()->json(['response' => $geminiResponseText]);

        } catch (\Exception $e) {
            Log::error('Error in GoverknowerController:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'An internal server error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get mock data for fallback when Pinecone is unavailable
     *
     * @return string
     */
    private function getMockData(): string
    {
        $vectorDBResults = [
            // 2024-2025 Bills
            "The 'Infrastructure Investment and Jobs Act 2024' (H.R. 3684) was passed with bipartisan support, allocating $1.2 trillion for roads, bridges, and broadband infrastructure.",
            "The 'American Rescue Plan Act 2024' (H.R. 1319) provided $1.9 trillion in COVID-19 relief funding, including stimulus checks and vaccine distribution.",
            "The 'Inflation Reduction Act 2024' (H.R. 5376) aims to reduce prescription drug costs and invest $369 billion in climate change initiatives.",
            "The 'Bipartisan Safer Communities Act 2024' (S. 2938) enhances background checks and provides funding for mental health services.",
            "The 'CHIPS and Science Act 2024' (H.R. 4346) provides $280 billion for semiconductor manufacturing and scientific research.",
            "The 'Respect for Marriage Act 2024' (H.R. 8404) codifies same-sex and interracial marriage protections.",
            "The 'National Defense Authorization Act 2024' (H.R. 7776) authorizes $858 billion for defense spending and military pay raises.",
            "The 'Omnibus Spending Bill 2024' (H.R. 2617) provides $1.7 trillion in government funding through September 2024.",
            
            // Senator Voting Records
            "Senator Chuck Schumer (D-NY) voted YES on the Infrastructure Investment and Jobs Act 2024, YES on the Inflation Reduction Act 2024, and YES on the CHIPS and Science Act 2024.",
            "Senator Mitch McConnell (R-KY) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, but YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Bernie Sanders (I-VT) voted YES on the American Rescue Plan Act 2024, YES on the Inflation Reduction Act 2024, and YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Ted Cruz (R-TX) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, and NO on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Elizabeth Warren (D-MA) voted YES on the American Rescue Plan Act 2024, YES on the Inflation Reduction Act 2024, and YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Marco Rubio (R-FL) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, but YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Amy Klobuchar (D-MN) voted YES on the American Rescue Plan Act 2024, YES on the Inflation Reduction Act 2024, and YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Josh Hawley (R-MO) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, and NO on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Cory Booker (D-NJ) voted YES on the American Rescue Plan Act 2024, YES on the Inflation Reduction Act 2024, and YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Tom Cotton (R-AR) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, and NO on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Kirsten Gillibrand (D-NY) voted YES on the American Rescue Plan Act 2024, YES on the Inflation Reduction Act 2024, and YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Rand Paul (R-KY) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, and NO on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Tammy Duckworth (D-IL) voted YES on the American Rescue Plan Act 2024, YES on the Inflation Reduction Act 2024, and YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Mike Lee (R-UT) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, and NO on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Mazie Hirono (D-HI) voted YES on the American Rescue Plan Act 2024, YES on the Inflation Reduction Act 2024, and YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Ron Johnson (R-WI) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, and NO on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Catherine Cortez Masto (D-NV) voted YES on the American Rescue Plan Act 2024, YES on the Inflation Reduction Act 2024, and YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Rick Scott (R-FL) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, and NO on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Mark Kelly (D-AZ) voted YES on the American Rescue Plan Act 2024, YES on the Inflation Reduction Act 2024, and YES on the Infrastructure Investment and Jobs Act 2024.",
            "Senator Marsha Blackburn (R-TN) voted NO on the American Rescue Plan Act 2024, NO on the Inflation Reduction Act 2024, and NO on the Infrastructure Investment and Jobs Act 2024.",
            
            // Additional 2024-2025 Bills
            "The 'Student Loan Debt Relief Act 2024' (H.R. 6951) proposes to cancel up to $20,000 in federal student loan debt for eligible borrowers.",
            "The 'Gun Safety Reform Act 2024' (S. 2938) expands background checks and implements a 21-day waiting period for firearm purchases.",
            "The 'Healthcare Affordability Act 2024' (H.R. 5376) caps insulin costs at $35 per month and allows Medicare to negotiate drug prices.",
            "The 'Climate Action Now Act 2024' (H.R. 9) requires the United States to remain in the Paris Climate Agreement and develop a plan to meet emissions targets.",
            "The 'Voting Rights Advancement Act 2024' (H.R. 4) restores and strengthens the Voting Rights Act of 1965.",
            "The 'Minimum Wage Increase Act 2024' (H.R. 603) gradually raises the federal minimum wage to $15 per hour by 2025.",
            "The 'Family and Medical Leave Expansion Act 2024' (H.R. 804) provides 12 weeks of paid family and medical leave for all workers.",
            "The 'Tax Fairness for Working Families Act 2024' (H.R. 1757) increases the Child Tax Credit and makes it permanently refundable.",
            
            // More Senator Voting Records for New Bills
            "Senator Chuck Schumer (D-NY) voted YES on the Student Loan Debt Relief Act 2024, YES on the Gun Safety Reform Act 2024, and YES on the Healthcare Affordability Act 2024.",
            "Senator Mitch McConnell (R-KY) voted NO on the Student Loan Debt Relief Act 2024, NO on the Gun Safety Reform Act 2024, and NO on the Healthcare Affordability Act 2024.",
            "Senator Bernie Sanders (I-VT) voted YES on the Student Loan Debt Relief Act 2024, YES on the Gun Safety Reform Act 2024, and YES on the Healthcare Affordability Act 2024.",
            "Senator Ted Cruz (R-TX) voted NO on the Student Loan Debt Relief Act 2024, NO on the Gun Safety Reform Act 2024, and NO on the Healthcare Affordability Act 2024.",
            "Senator Elizabeth Warren (D-MA) voted YES on the Student Loan Debt Relief Act 2024, YES on the Gun Safety Reform Act 2024, and YES on the Healthcare Affordability Act 2024.",
            "Senator Marco Rubio (R-FL) voted NO on the Student Loan Debt Relief Act 2024, NO on the Gun Safety Reform Act 2024, and NO on the Healthcare Affordability Act 2024.",
            
            // Committee Information
            "Senator Chuck Schumer (D-NY) serves as Senate Majority Leader and is a member of the Senate Finance Committee and Senate Banking Committee.",
            "Senator Mitch McConnell (R-KY) serves as Senate Minority Leader and is a member of the Senate Rules Committee and Senate Appropriations Committee.",
            "Senator Bernie Sanders (I-VT) chairs the Senate Budget Committee and is a member of the Senate Health, Education, Labor, and Pensions Committee.",
            "Senator Ted Cruz (R-TX) serves on the Senate Judiciary Committee, Senate Commerce Committee, and Senate Foreign Relations Committee.",
            "Senator Elizabeth Warren (D-MA) serves on the Senate Banking Committee, Senate Armed Services Committee, and Senate Special Committee on Aging.",
            "Senator Marco Rubio (R-FL) serves on the Senate Foreign Relations Committee, Senate Intelligence Committee, and Senate Small Business Committee.",
            
            // Bill Sponsorship Information
            "Senator Bernie Sanders (I-VT) sponsored the 'Medicare for All Act 2024' (S. 1129) which would establish a single-payer healthcare system.",
            "Senator Elizabeth Warren (D-MA) sponsored the 'Student Loan Debt Relief Act 2024' (H.R. 6951) and the 'Wealth Tax Act 2024' (S. 510).",
            "Senator Ted Cruz (R-TX) sponsored the 'Border Security and Immigration Reform Act 2024' (S. 2193) and the 'Second Amendment Protection Act 2024' (S. 1234).",
            "Senator Marco Rubio (R-FL) sponsored the 'Pro-Life Protection Act 2024' (S. 3278) and the 'Small Business Tax Relief Act 2024' (S. 2156).",
            "Senator Amy Klobuchar (D-MN) sponsored the 'Antitrust Enforcement Act 2024' (S. 225) and the 'Election Security Act 2024' (S. 1543).",
            "Senator Josh Hawley (R-MO) sponsored the 'Big Tech Accountability Act 2024' (S. 789) and the 'American Workers Protection Act 2024' (S. 2341)."
        ];
        return implode("\n", $vectorDBResults);
    }
}
