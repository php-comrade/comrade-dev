import {Injectable} from '@angular/core';
import {Job} from "./job";
import {JobService} from "./job.service";
import {WampService} from "./wamp.service";
import {EventMessage} from "thruway.js/src/Messages/EventMessage";

import {Observable} from "rxjs/Observable";
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/do';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/distinctUntilChanged';
import 'rxjs/add/operator/merge';
import 'rxjs/add/operator/withLatestFrom';
import {ReplaySubject} from "rxjs/ReplaySubject";
import {CurrentJobService} from "./current-job.service";
import {GetSubJobs} from "./messages/get-sub-jobs";
import {SubJob} from "./sub-job";

@Injectable()
export class CurrentSubJobsService {
    subJobs: SubJob[];
    currentSubJobs: ReplaySubject<SubJob[]>;

    constructor(private currentJobService: CurrentJobService, private jobService: JobService, private wamp: WampService) {
        this.currentSubJobs = new ReplaySubject(1);

        wamp.topic('comrade.job_updated')
            .map((event: EventMessage) => event.args[0])
            .filter((job: SubJob) => !!job.parentId)
            .withLatestFrom(this.currentJobService.getCurrentJob())
            .filter(([job, currentJobId]) => job.parentId === currentJobId.id)
            .map(([job, currentJobId]) => job)
            .subscribe((updatedJob: SubJob) => {
              let subJobs = [...this.subJobs];
              const index = subJobs.findIndex((job: Job) => job.id === updatedJob.id);
              if (~index) {
                if (updatedJob.updatedAt.unix >= subJobs[index].updatedAt.unix) {
                  subJobs[index] = updatedJob;
                }
              } else {
                subJobs.push(updatedJob);
              }

              this.subJobs = subJobs;
              this.currentSubJobs.next(subJobs);
            })
        ;

        this.currentJobService.getCurrentJob()
            .do(() => this.currentSubJobs.next(null))
            .filter((job: Job) => !!job)
            .switchMap((job: Job) => {
                return this.jobService.getSubJobs(new GetSubJobs(job.id))
            })
            .shareReplay(1)
            .subscribe((jobs: SubJob[]) => {
                this.subJobs = jobs;
                this.currentSubJobs.next(jobs);
            })
        ;
    }

    getCurrentSubJobs(): Observable<SubJob[]> {
        return this.currentSubJobs;
    }
}
