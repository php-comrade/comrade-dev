import { Injectable } from '@angular/core';
import { JobTemplate } from './job-template';
import { Headers, Http, Response } from '@angular/http';
import {Job} from "./job";

import 'rxjs/add/operator/map';
import 'rxjs/add/operator/catch';
import {Observable} from "rxjs/Observable";
import {GetTimeline} from "./messages/get-timeline";

@Injectable()
export class TimelineService {
    private apiBaseUrl = 'http://jm.loc/api/job-templates';

    private headers = new Headers({'Content-Type': 'application/json'});

    constructor(private http: Http) { }

    getTimelineDone(getTimeline: GetTimeline): Observable<Job[]> {
        return this.http.post('http://jm.loc/api/jobs/timeline-done', JSON.stringify(getTimeline), {headers: this.headers})
            .map((response: Response) => response.json().jobs as Job[])
            .catch(this.handleError);
    }

    getTimelineFuture(getTimeline: GetTimeline): Observable<Job[]> {
        return this.http.post('http://jm.loc/api/jobs/timeline-future', JSON.stringify(getTimeline), {headers: this.headers})
            .map((response: Response) => response.json().jobs as Job[])
            .catch(this.handleError);
    }

    private handleError(error: any): Promise<any> {
        console.error('An error occurred', error); // for demo purposes only
        return Promise.reject(error.message || error);
    }
}
