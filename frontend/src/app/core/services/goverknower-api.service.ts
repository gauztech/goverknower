import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { GeminiAPIService } from './gemini-api.service';
import { BackendAPIService } from './backend-api.service';

@Injectable({
    providedIn: 'root',
    useClass: GeminiAPIService,
})
export abstract class GoverknowerAPIService {
    public abstract sendMessage(message: string): Observable<string> | null;
}
