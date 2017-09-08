import {Injectable} from '@angular/core';
import {WampService} from "./wamp.service";
import {EventMessage} from "thruway.js/src/Messages/EventMessage";

import {Observable} from "rxjs/Observable";
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/distinctUntilChanged';
import 'rxjs/add/operator/merge';
import 'rxjs/add/operator/withLatestFrom';
import {JobTemplateService} from "./job-template.service";
import {ReplaySubject} from "rxjs/ReplaySubject";
import {JobTemplate} from "./job-template";

@Injectable()
export class CurrentJobTemplateService {
    currentJobTemplateId: ReplaySubject<string>;
    currentJobTemplate: ReplaySubject<JobTemplate>;

    constructor(private jobTemplateService: JobTemplateService, private wamp: WampService) {
        this.currentJobTemplateId = new ReplaySubject(1);
        this.currentJobTemplate = new ReplaySubject(1);

        wamp.topic('comrade.job_template_updated')
            .map((event: EventMessage) => event.args[0])
            .withLatestFrom(this.currentJobTemplateId)
            .filter(([jobTemplate, currentJobTemplateId]) => jobTemplate.templateId === currentJobTemplateId)
            .map(([jobTemplate, currentJobTemplateId]) => jobTemplate)
            .subscribe((jobTemplate: JobTemplate) => this.currentJobTemplate.next(jobTemplate))
        ;

        this.currentJobTemplateId
            .distinctUntilChanged()
            .do(() => this.currentJobTemplate.next(null))
            .switchMap((id: string) => {
                return this.jobTemplateService.getJobTemplate(id).catch(() => Observable.empty());
            })
            .shareReplay(1)
            .subscribe((jobTemplate: JobTemplate) => this.currentJobTemplate.next(jobTemplate))
        ;
    }


    change(jobTemplateId: string) {
        this.currentJobTemplateId.next(jobTemplateId);
    }

    getCurrentJobTemplate(): Observable<JobTemplate> {
        return this.currentJobTemplate;
    }
}
