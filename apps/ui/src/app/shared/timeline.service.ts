import { Injectable } from '@angular/core';
import { Response } from '@angular/http';
import {Job} from "./job";

import 'rxjs/add/operator/map';
import 'rxjs/add/operator/catch';
import {Observable} from "rxjs/Observable";
import {GetTimeline} from "./messages/get-timeline";
import {HttpService} from "./http.service";

@Injectable()
export class TimelineService {
    constructor(private http: HttpService) { }

    getTimelineDone(getTimeline: GetTimeline): Observable<Job[]> {
        return this.http.post('/api/jobs/timeline-done', getTimeline)
            .map((response: Response) => response.json().jobs as Job[])
            .catch((response: Response) => Observable.throw(response));
    }

    getTimelineFuture(getTimeline: GetTimeline): Observable<Job[]> {
        return this.http.post('/api/jobs/timeline-future', getTimeline)
            .map((response: Response) => response.json().jobs as Job[])
            .catch((response: Response) => Observable.throw(response));
    }
}
