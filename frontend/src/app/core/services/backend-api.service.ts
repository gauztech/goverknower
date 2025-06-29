import { inject, Injectable } from '@angular/core';
import { GoverknowerAPIService } from './goverknower-api.service';
import { Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { env } from '../../../environment/environment';

@Injectable({
    providedIn: 'root',
})
export class BackendAPIService implements GoverknowerAPIService {
    private http = inject(HttpClient);

    public sendMessage(message: string): Observable<string> | null {
        let response: Observable<any> | null = null;

        try {
            response = this.http.get(env.API_URL + message);
        } catch (error) {
            console.log(error);
        }

        return response;
    }
}
