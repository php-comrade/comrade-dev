import { Injectable } from '@angular/core';
import { JobTemplate } from './job-template';
import { Headers, Http } from '@angular/http';
import {Job} from "./job";
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/catch';
import {Observable} from "rxjs/Observable";
import {GetJob} from "./messages/get-job";
import {GetSubJobs} from "./messages/get-sub-jobs";

@Injectable()
export class JobService {
    private apiBaseUrl = 'http://jm.loc/api/job-templates';

    private headers = new Headers({'Content-Type': 'application/json'});

    constructor(private http: Http) { }

    getJobs(jobTemplate: JobTemplate): Promise<Job[]> {
        return this.http.get(this.apiBaseUrl + '/' + jobTemplate.templateId + '/jobs')
            .toPromise()
            .then(response => response.json().jobs as Job[])
            .catch(this.handleError);
    }

    getSubJobs(getSubJobs: GetSubJobs): Observable<Job[]> {
        return this.http.post(`http://jm.loc/api/get-sub-jobs`, JSON.stringify(getSubJobs), {headers: this.headers})
            .map(response => response.json().subJob as Job[])
            .catch((response: Response) => Observable.throw(response));
    }

    getJob(getJob: GetJob): Observable<Job> {
        return this.http.post(`http://jm.loc/api/get-job`, JSON.stringify(getJob), {headers: this.headers})
            .map(response => response.json().job as Job)
            .catch((response: Response) => Observable.throw(response));
    }

    private handleError(error: any): Promise<any> {
        console.error('An error occurred', error); // for demo purposes only
        return Promise.reject(error.message || error);
    }
}
