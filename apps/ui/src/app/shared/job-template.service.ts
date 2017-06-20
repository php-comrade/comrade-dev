import { Injectable } from '@angular/core';
import { JobTemplate } from './job-template';
import { Headers, Http } from '@angular/http';

import 'rxjs/add/operator/toPromise';

@Injectable()
export class JobTemplateService {
    private heroesUrl = 'http://jm.loc/api/job-templates';

    private headers = new Headers({'Content-Type': 'application/json'});

    constructor(private http: Http) { }

    getJobTemplates(): Promise<JobTemplate[]> {
        return this.http.get(this.heroesUrl)
            .toPromise()
            .then(response => response.json().jobTemplates as JobTemplate[])
            .catch(this.handleError);
    }

    private handleError(error: any): Promise<any> {
        console.error('An error occurred', error); // for demo purposes only
        return Promise.reject(error.message || error);
    }
}