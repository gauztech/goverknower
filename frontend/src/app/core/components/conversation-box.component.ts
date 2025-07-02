import { Component, inject } from '@angular/core';
import { ConversationController } from '../controllers/conversation.controller';
import { Message } from '../models/message.model';
import { CommonModule } from '@angular/common';

@Component({
    selector: 'conversation-box',
    templateUrl: './conversation-box.component.html',
    styleUrl: './conversation-box.component.css',
    imports: [CommonModule],
})
export class ConversationBoxComponent {
    private controller = inject(ConversationController);
    public messages: Message[] = [];
    public aiThinking = false;

    constructor() {
        this.controller.getConversation().msgObsever$.subscribe((newMsg) => {
            this.messages = newMsg;
        });

        this.controller.aiThinking$.subscribe((thinking) => {
            this.aiThinking = thinking;
        })
    }

    askExample(question: string): void {
        this.controller.sendMessage(question);
    }
}
