import {Injectable} from '@angular/core';
import {Client} from 'thruway.js';
import {HttpService} from "./http.service";
import {Observable} from "rxjs/Observable";
import {Subscription} from "rxjs/Subscription";

@Injectable()
export class WampService {
    private wampClient: Client;

    constructor(private http: HttpService) {
        this.http.getApiBaseUrlObservable().subscribe((apiUrl: string) => {
            if (this.wampClient) {
                this.wampClient.close();
            }

            let url = new URL(apiUrl);
            this.wampClient = new Client(`ws://${url.hostname}:9090/`, 'realm1');
        });
    }

    publish(uri: string, value: Observable<any> | any, options?: Object): Subscription {
        return this.wampClient.publish(uri, value, options);
    }

    topic(uri: string, options?: Object): Observable<any> {
        return this.wampClient.topic(uri, options);
    }
}
