import { HttpClient, HttpHeaders } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable, pipe, map } from 'rxjs';
import { env } from '../../../environment/environment';
import { GoverknowerAPIService } from './goverknower-api.service';

@Injectable({
    providedIn: 'root',
})
export class GeminiAPIService implements GoverknowerAPIService {
    private http = inject(HttpClient);

    public sendMessage(message: string): Observable<string> | null {
        const header: HttpHeaders = new HttpHeaders({
            'Content-Type': 'application/json',
        });

        const body: string = this.prepareContent(message);

        const response: Observable<any> = this.http.post(
            env.GEMINI_URL + env.GEMINI_API_KEY,
            body,
            {
                headers: header,
                responseType: 'json',
            },
        );

        return response.pipe(
            map((data) => {
                console.log("[Gemini]", data);
                return data.candidates[0].content.parts[0].text;
            }),
        );
    }

    private prepareContent(message: string): string {
        let content: string =
            `{
            "contents": [
                {
                    "parts": [{
                        "text":"` +
            message +
            `"
                    }]
                }
            ]
        }`;

        return content;
    }
}
