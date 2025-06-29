import { inject, Injectable } from '@angular/core';
import * as uuid from 'uuid';
import { GoverknowerAPIService } from '../services/goverknower-api.service';
import { Conversation } from '../models/conversation.model';

@Injectable({
    providedIn: 'root',
})
export class ConversationController {
    private api = inject(GoverknowerAPIService);
    private conversation: Conversation;

    public isThinking = false;

    constructor() {
        const conversationId = uuid.v4();
        this.conversation = new Conversation(conversationId);
    }

    public sendMessage(message: string) {
        // Add user's message to conversation
        this.conversation.addMessage(message, 'user');

        this.isThinking = true;

        this.api.sendMessage(message)?.subscribe({
            next: (response) => {
                // on successful, add AI's message to the conversation
                this.conversation.addMessage(response, 'ai');
            },
            error: (error) => {
                console.error("Error generating AI response:", error);
            },
            complete: () => {
                this.isThinking = false;
            }
        });
    }

    public getConversation(): Conversation {
        return this.conversation;
    }
}
