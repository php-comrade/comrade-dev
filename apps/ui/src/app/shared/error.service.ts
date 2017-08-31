import { Injectable } from '@angular/core';
import {Response} from '@angular/http';

import 'rxjs/add/observable/throw';
import {Observable} from "rxjs/Observable";
import {ServerError} from "./server-error";
import {HttpService} from "./http.service";

@Injectable()
export class ErrorService {
    constructor(private http: HttpService) { }

    getLateErrors(): Observable<ServerError[]> {
        return this.http.get(`/api/errors/late`)
            .map(response => response.json().errors as ServerError[])
            .catch((response: Response) => Observable.throw(response));
    }

    deleteOlderThan(olderMileSeconds: number): Observable<Response> {
        const olderMicroSeconds = olderMileSeconds * 1000;

        return this.http.delete(`/api/errors?older=${olderMicroSeconds}`)
            .catch((response: Response) => Observable.throw(response));
    }

    deleteAll(): Observable<Response> {
        return this.http.delete(`/api/errors?all=1`)
            .catch((response: Response) => Observable.throw(response));
    }
}
