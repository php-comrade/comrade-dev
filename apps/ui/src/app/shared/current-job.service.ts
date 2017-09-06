import {Injectable} from '@angular/core';
import {Job} from "./job";
import {JobService} from "./job.service";
import {GetJob} from "./messages/get-job";
import {WampService} from "./wamp.service";
import {EventMessage} from "thruway.js/src/Messages/EventMessage";

import {BehaviorSubject} from "rxjs/BehaviorSubject";
import {Observable} from "rxjs/Observable";
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/catch';
import 'rxjs/add/operator/shareReplay';
import 'rxjs/add/operator/mergeMap';
import 'rxjs/add/operator/merge';

@Injectable()
export class CurrentJobService {
    currentJobId: BehaviorSubject<string>;
    currentJob: Observable<Job>;

    constructor(private jobService: JobService, private wamp: WampService) {
        this.currentJobId = new BehaviorSubject('');

        this.currentJob = this.currentJobId
            .flatMap((id: string) => this.jobService.getJob(new GetJob(id)))
            .merge(wamp.topic('job_manager.update_job')
                .map((event: EventMessage) => event.args.pop())
                .filter((job: Job) => job.id === this.currentJobId.value)
            ).shareReplay(1);
    }

    change(jobId: string) {
        if (this.currentJobId.value !== jobId) {
            this.currentJobId.next(jobId);
        }
    }

    getCurrentJob(): Observable<Job> {
        return this.currentJob;
    }
}
