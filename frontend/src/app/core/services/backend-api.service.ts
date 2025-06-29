import { inject, Injectable } from '@angular/core';
import { GoverknowerAPIService } from './goverknower-api.service';
import { Observable, map } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { env } from '../../../environment/environment';

interface BackendResponse {
    response: string;
}

@Injectable({
    providedIn: 'root',
})
export class BackendAPIService implements GoverknowerAPIService {
    private http = inject(HttpClient);

    public sendMessage(message: string): Observable<string> | null {
        try {
            const response = this.http.get<BackendResponse>(env.API_URL + message);
            return response.pipe(
                map((data) => {
                    console.log("[Backend API]", data);
                    return data.response;
                })
            );
        } catch (error) {
            console.log(error);
            return null;
        }
    }
}
