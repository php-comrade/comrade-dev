import { Injectable } from '@angular/core';
import { JobTemplate } from './job-template';
import {Response} from '@angular/http';

import 'rxjs/add/operator/toPromise';
import 'rxjs/add/operator/map';
import 'rxjs/add/observable/throw';
import {CreateJob} from "./messages/create-job";
import {Observable} from "rxjs/Observable";
import {AddTrigger} from "./messages/add-trigger";
import {SimpleTrigger} from "./simple-trigger";
import {Date} from "./date";
import "rxjs/add/operator/share";
import {HttpService} from "./http.service";

@Injectable()
export class JobTemplateService {
    constructor(private http: HttpService) { }

    getJobTemplates(): Promise<JobTemplate[]> {
        return this.http.get('/api/job-templates')
            .toPromise()
            .then(response => response.json().jobTemplates as JobTemplate[])
            .catch(this.handleError);
    }

    getJobTemplate(id: string): Observable<JobTemplate> {
        return this.http.get(`/api/job-templates/${id}`).map((res: Response) => res.json().data);
    }

    runNow(jobTemplate: JobTemplate): Observable<JobTemplate> {
        let moment = require('moment');

        const simpleTrigger = new SimpleTrigger();
        simpleTrigger.misfireInstruction = 'fire_now';
        simpleTrigger.startAt = Date.fromMoment(moment());
        simpleTrigger.repeatCount = 0;
        simpleTrigger.intervalInSeconds = 0;

        const addTrigger = new AddTrigger(jobTemplate.templateId, simpleTrigger);

        return this.http.post('/api/add-trigger', addTrigger)
            .map((response: Response) => response.json().jobTemplate as JobTemplate)
            .catch((response: Response) => Observable.throw(response));
    }

    create(jobTemplate: JobTemplate): Observable<Response> {
        let createJob = new CreateJob(jobTemplate);

        return this.http.post('/api/job-templates', createJob);
    }

    private handleError(error: any): Promise<any> {
        console.error('An error occurred', error); // for demo purposes only
        return Promise.reject(error.message || error);
    }
}