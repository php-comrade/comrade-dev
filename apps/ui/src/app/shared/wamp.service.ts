import {Injectable} from '@angular/core';
import {Client} from 'thruway.js';
import {HttpService} from "./http.service";
import {Observable} from "rxjs/Observable";
import {Subscription} from "rxjs/Subscription";
import {ReplaySubject} from "rxjs/ReplaySubject";

@Injectable()
export class WampService {
    private wampClient: Client;
    private wampBaseUrl: ReplaySubject<string>;

    constructor(private http: HttpService) {
        this.wampBaseUrl = new ReplaySubject(1);

        this.http.getApiBaseUrlObservable().subscribe((apiUrl: string) => {
            if (this.wampClient) {
                this.wampClient.close();
            }

            let url = new URL(apiUrl);
            this.wampClient = new Client(`ws://${url.hostname}:9090/`, 'realm1');
            this.wampBaseUrl.next(`ws://${url.hostname}:9090/`);
        });
    }

    publish(uri: string, value: Observable<any> | any, options?: Object): Subscription {
        return this.wampClient.publish(uri, value, options);
    }

    topic(uri: string, options?: Object): Observable<any> {
        return this.wampClient.topic(uri, options);
    }

    getWampBaseUrl():Observable<string> {
        return this.wampBaseUrl;
    }
}
