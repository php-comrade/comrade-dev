import {Injectable} from '@angular/core';
import { Headers, Http, Response } from '@angular/http';

import 'rxjs/add/operator/map';
import 'rxjs/add/operator/catch';
import 'rxjs/add/operator/merge';
import 'rxjs/add/operator/do';
import 'rxjs/observable/fromEvent'
import {Observable} from "rxjs/Observable";
import {BehaviorSubject} from "rxjs/BehaviorSubject";
import {LocalStorageService} from "ngx-webstorage";

@Injectable()
export class HttpService {
    private headers: Headers;
    private changeObservable: BehaviorSubject<string>;

    constructor(private http: Http, private localStorage: LocalStorageService) {
        this.headers = new Headers({'Content-Type': 'application/json', 'Accept': 'application/json'});

        let storedApiBaseUrl: string = this.localStorage.retrieve('api_base_url');

        this.changeObservable = new BehaviorSubject(storedApiBaseUrl ? storedApiBaseUrl : '');
    }

    get(relativeUrl: string): Observable<Response> {
        return this.http.get(this.getApiBaseUrl() + relativeUrl);
    }

    post(relativeUrl: string, data: object): Observable<Response> {
        return this.http.post(this.getApiBaseUrl() + relativeUrl, JSON.stringify(data), {headers: this.headers});
    }

    put(relativeUrl: string, data: object): Observable<Response> {
        return this.http.put(this.getApiBaseUrl() + relativeUrl, JSON.stringify(data), {headers: this.headers});
    }

    patch(relativeUrl: string, data: object): Observable<Response> {
        return this.http.patch(this.getApiBaseUrl() + relativeUrl, JSON.stringify(data), {headers: this.headers});
    }

    delete(relativeUrl: string): Observable<Response> {
        return this.http.delete(this.getApiBaseUrl() + relativeUrl, {headers: this.headers});
    }

    getInfo(apiBaseUrl: string): Observable<Response> {
        apiBaseUrl = apiBaseUrl.replace(/\/$/, "");

        return this.http.get(apiBaseUrl + '/api/info', {headers: this.headers});
    }

    changeApiBaseUrl(apiBaseUrl: string, store: boolean) {
        this.changeObservable.next(apiBaseUrl.replace(/\/$/, ""));

        if (store) {
            this.localStorage.store('api_base_url', apiBaseUrl);
        }
    }

    getApiBaseUrl(): string {
        return this.changeObservable.value;
    }

    getApiBaseUrlObservable(): Observable<string> {
        return this.changeObservable;
    }
}
