import { Injectable } from '@angular/core';
import { JobTemplate } from './job-template';
import {Response} from '@angular/http';

import 'rxjs/add/operator/toPromise';
import 'rxjs/add/operator/map';
import 'rxjs/add/observable/throw';
import {CreateJob} from "./messages/create-job";
import {Observable} from "rxjs/Observable";
import "rxjs/add/operator/share";
import {HttpService} from "./http.service";
import {ScheduleJob} from "./messages/schedule-job";
import {NowTrigger} from "./now-trigger";

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
        return this.http.get(`/api/job-templates/${id}`).map((res: Response) => {
            return Object.assign(new JobTemplate(), res.json().data);
        });
    }

    runNow(jobTemplate: JobTemplate): Observable<Response> {
        const trigger = new NowTrigger();
        trigger.templateId = jobTemplate.templateId;

        return this.http.post('/api/schedule-job', new ScheduleJob(trigger))
            .catch((response: Response) => Observable.throw(response))
        ;
    }

    create(createJob: CreateJob): Observable<Response> {
        return this.http.post('/api/create-job', createJob);
    }

    private handleError(error: any): Promise<any> {
        console.error('An error occurred', error); // for demo purposes only
        return Promise.reject(error.message || error);
    }
}