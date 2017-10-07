import {Injectable} from '@angular/core';
import {Job} from "./job";
import {JobService} from "./job.service";
import {GetJob} from "./messages/get-job";
import {WampService} from "./wamp.service";
import {EventMessage} from "thruway.js/src/Messages/EventMessage";

import {Observable} from "rxjs/Observable";
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/distinctUntilChanged';
import 'rxjs/add/operator/merge';
import 'rxjs/add/operator/withLatestFrom';
import {ReplaySubject} from "rxjs/ReplaySubject";

@Injectable()
export class CurrentJobService {
    currentJobId: ReplaySubject<string>;
    currentJob: ReplaySubject<Job>;

    constructor(private jobService: JobService, private wamp: WampService) {
        this.currentJobId = new ReplaySubject(1);
        this.currentJob = new ReplaySubject(1);

        wamp.topic('comrade.job_updated')
            .map((event: EventMessage) => event.args[0])
            .withLatestFrom(this.currentJob)
            .filter(([job, currentJob]) => {
                if (!currentJob) {
                    return false;
                }

                return job.id === currentJob.id;
            })
            .filter(([job, currentJob]) => job.updatedAt.unix >= currentJob.updatedAt.unix)
            .map(([job, currentJob]) => job)
            .subscribe((job: Job) => this.currentJob.next(job))
        ;

        this.currentJobId
            .distinctUntilChanged()
            .do(() => this.currentJob.next(null))
            .switchMap((id: string) => {
                return this.jobService.getJob(new GetJob(id)).catch(() => Observable.empty())
            })
            .shareReplay(1)
            .subscribe((job: Job) => {
                this.currentJob.next(job)
            })
        ;
    }


    change(jobId: string) {
        this.currentJobId.next(jobId);
    }

    getCurrentJob(): Observable<Job> {
        return this.currentJob;
    }
}
