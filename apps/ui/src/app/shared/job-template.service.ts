import { Injectable } from '@angular/core';
import { JobTemplate } from './job-template';
import {Headers, Http, Response} from '@angular/http';

import 'rxjs/add/operator/toPromise';
import {CreateJob} from "./messages/create-job";
import {Observable} from "rxjs/Observable";
import {CronTrigger} from "./cron-trigger";
import {AddTrigger} from "./messages/add-trigger";
import {SimpleTrigger} from "./simple-trigger";

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

    runNow(jobTemplate: JobTemplate): Observable<Response> {
        const url = 'http://jm.loc/api/add-trigger';

        const simpleTrigger = new SimpleTrigger();
        simpleTrigger.misfireInstruction = 'fire_now';

        const addTrigger = new AddTrigger(jobTemplate.templateId, simpleTrigger);

        return this.http.post(url, JSON.stringify(addTrigger), {headers: this.headers});
    }

    create(jobTemplate: JobTemplate): Observable<Response> {
        let createJob = new CreateJob(jobTemplate);

        return this.http.post(
            this.apiBaseUrl,
            JSON.stringify(createJob),
            {headers: this.headers}
        );
    }

    private handleError(error: any): Promise<any> {
        console.error('An error occurred', error); // for demo purposes only
        return Promise.reject(error.message || error);
    }
}