import { Component, inject } from '@angular/core';
import { ConversationController } from '../controllers/conversation.controller';
import { Message } from '../models/message.model';

@Component({
    selector: 'conversation-box',
    templateUrl: './conversation-box.component.html',
    styleUrl: './conversation-box.component.css',
})
export class ConversationBoxComponent {
    private controller = inject(ConversationController);
    public messages: Message[] = [];

    constructor() {
        this.controller.getConversation().msgObsever$.subscribe((newMsg) => {
            this.messages = newMsg;
        });
    }
}
