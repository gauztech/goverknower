import { Component } from '@angular/core';
import { MessageBoxComponent } from './core/components/message-box.component';
import { ConversationBoxComponent } from './core/components/conversation-box.component';
import { GoverknowerAPIService } from './core/services/goverknower-api.service';
import { GeminiAPIService } from './core/services/gemini-api.service';
import { SideDrawerComponent } from './core/components/sidedrawer.component';

@Component({
    selector: 'app-root',
    imports: [
        MessageBoxComponent,
        ConversationBoxComponent,
        SideDrawerComponent,
    ],
    templateUrl: './app.component.html',
    styleUrl: './app.component.css',
    providers: [
        {
            provide: GoverknowerAPIService,
            useClass: GeminiAPIService,
        },
    ],
})
export class AppComponent {
    title = 'Goverknower - Ask About Your Politician';
    showHelp = false;
}
