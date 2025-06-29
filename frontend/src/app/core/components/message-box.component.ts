import { Component, inject } from '@angular/core';
import { ConversationController } from '../controllers/conversation.controller';

@Component({
    selector: 'message-box',
    templateUrl: './message-box.component.html',
    styleUrl: './message-box.component.css',
})
export class MessageBoxComponent {
    private controller = inject(ConversationController);

    public sendMessage(message: string) {
        this.controller.sendMessage(message);
    }
}
