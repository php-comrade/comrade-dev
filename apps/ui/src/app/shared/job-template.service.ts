import { Injectable } from '@angular/core';
import { JobTemplate } from './job-template';
import { Headers, Http } from '@angular/http';

import 'rxjs/add/operator/toPromise';
import {CreateJob} from "./messages/create-job";

@Injectable()
export class JobTemplateService {
    private apiBaseUrl = 'http://jm.loc/api/job-templates';

    private headers = new Headers({'Content-Type': 'application/json'});

    constructor(private http: Http) { }

    getJobTemplates(): Promise<JobTemplate[]> {
        return this.http.get(this.apiBaseUrl)
            .toPromise()
            .then(response => response.json().jobTemplates as JobTemplate[])
            .catch(this.handleError);
    }

    getJobTemplate(id: string): Promise<JobTemplate> {
        const url = `${this.apiBaseUrl}/${id}`;

        return this.http.get(url)
            .toPromise()
            .then(response => response.json().data as JobTemplate)
            .catch(this.handleError);
    }

    runNow(id: string): void {
        const url = `${this.apiBaseUrl}/${id}/run-now`;

        this.http.post(url, '', {headers: this.headers})
            .toPromise()
            .then(response => console.log(response.json()))
            .catch(this.handleError);
    }

    create(jobTemplate: JobTemplate): Promise<void> {
        let createJob = new CreateJob(jobTemplate);

        return this.http
            .post(this.apiBaseUrl, JSON.stringify(createJob), {headers: this.headers})
            .toPromise()
            .then(res => console.log(res.json()))
            .catch(this.handleError);
    }

    private handleError(error: any): Promise<any> {
        console.error('An error occurred', error); // for demo purposes only
        return Promise.reject(error.message || error);
    }
}