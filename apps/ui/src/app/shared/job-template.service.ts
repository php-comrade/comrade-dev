import { Injectable } from '@angular/core';
import { JobTemplate } from './job-template';
import { Headers, Http } from '@angular/http';

import 'rxjs/add/operator/toPromise';
import {CreateJob} from "./messages/create-job";

@Injectable()
export class JobTemplateService {
    private jobTemplatesUrl = 'http://jm.loc/api/job-templates';

    private headers = new Headers({'Content-Type': 'application/json'});

    constructor(private http: Http) { }

    getJobTemplates(): Promise<JobTemplate[]> {
        return this.http.get(this.jobTemplatesUrl)
            .toPromise()
            .then(response => response.json().jobTemplates as JobTemplate[])
            .catch(this.handleError);
    }

    create(jobTemplate: JobTemplate): Promise<void> {
        let createJob = new CreateJob(jobTemplate);

        return this.http
            .post(this.jobTemplatesUrl, JSON.stringify(createJob), {headers: this.headers})
            .toPromise()
            .then(res => console.log(res.json().data()))
            .catch(this.handleError);
    }

    private handleError(error: any): Promise<any> {
        console.error('An error occurred', error); // for demo purposes only
        return Promise.reject(error.message || error);
    }
}