import { Injectable } from '@angular/core';
import { JobTemplate } from './job-template';
import { Headers, Http } from '@angular/http';
import {Job} from "./job";

import 'rxjs/add/operator/toPromise';

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

    private handleError(error: any): Promise<any> {
        console.error('An error occurred', error); // for demo purposes only
        return Promise.reject(error.message || error);
    }
}
