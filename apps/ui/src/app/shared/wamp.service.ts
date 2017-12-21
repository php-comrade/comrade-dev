import {Injectable} from '@angular/core';
import {Client} from 'thruway.js';
import {HttpService} from "./http.service";
import {Observable} from "rxjs/Observable";
import {Subscription} from "rxjs/Subscription";
import {ReplaySubject} from "rxjs/ReplaySubject";
import {Response} from "@angular/http";

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

            this.http.getInfo(apiUrl).subscribe((res: Response) => {

              let wamp_dsn = res.json().wamp_dsn;
              let wamp_realm = res.json().wamp_realm;
              this.wampClient = new Client(wamp_dsn, wamp_realm);
              this.wampBaseUrl.next(wamp_dsn);
            });
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
