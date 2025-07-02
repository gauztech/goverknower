import { inject, Injectable } from '@angular/core';
import * as uuid from 'uuid';
import { GoverknowerAPIService } from '../services/goverknower-api.service';
import { Conversation } from '../models/conversation.model';
import { BehaviorSubject } from 'rxjs';

@Injectable({
    providedIn: 'root',
})
export class ConversationController {
    private api = inject(GoverknowerAPIService);
    private conversation: Conversation;

    public aiThinking$: BehaviorSubject<boolean>;

    constructor() {
        const conversationId = uuid.v4();
        this.conversation = new Conversation(conversationId);
        this.aiThinking$ = new BehaviorSubject<boolean>(false);
    }

    public sendMessage(message: string) {
        // Add user's message to conversation
        this.conversation.addMessage(message, 'user');

        this.aiThinking$.next(true);

        this.api.sendMessage(message)?.subscribe({
            next: (response) => {
                // on successful, add AI's message to the conversation
                this.conversation.addMessage(response, 'ai');
            },
            error: (error) => {
                console.error("Error generating AI response:", error);
            },
            complete: () => {
                this.aiThinking$.next(false);
            }
        });
    }

    public getConversation(): Conversation {
        return this.conversation;
    }
}
