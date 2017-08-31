import { Injectable } from '@angular/core';
import {Job} from "./job";
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/catch';
import {Observable} from "rxjs/Observable";
import {GetJob} from "./messages/get-job";
import {GetSubJobs} from "./messages/get-sub-jobs";
import {SubJob} from "./sub-job";
import {HttpService} from "./http.service";

@Injectable()
export class JobService {
    constructor(private http: HttpService) { }

    getSubJobs(getSubJobs: GetSubJobs): Observable<SubJob[]> {
        return this.http.post('/api/get-sub-jobs', getSubJobs)
            .map(response => response.json().subJobs as SubJob[])
            .catch((response: Response) => Observable.throw(response));
    }

    getJob(getJob: GetJob): Observable<Job> {
        return this.http.post('/api/get-job', getJob)
            .map(response => response.json().job as Job)
            .catch((response: Response) => Observable.throw(response));
    }
}
