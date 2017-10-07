import {Component, Input, OnInit} from '@angular/core';
import {TimelineService} from "../shared/timeline.service";
import {GetTimeline} from "../shared/messages/get-timeline";
import {Job as JobModel} from "../shared/job";
import 'rxjs/add/observable/interval';
import 'rxjs/add/operator/map';
import {WampService} from "../shared/wamp.service";
import {EventMessage} from "thruway.js/src/Messages/EventMessage";
import {Observable} from "rxjs/Observable";

interface Job extends JobModel {
    isFuture: boolean;
}

@Component({
  selector: 'timeline',
  template: `
      <p *ngIf="!jobs">Loading</p>

      <div *ngIf="jobs">
          <table class="table table-striped table-hover table-bordered">
              <thead>
                  <tr job-list-header-row></tr>
              </thead>
              <tbody>
              <ng-container  *ngFor="let job of jobs; let i = index;">
                  <tr *ngIf="!i && !job.isFuture"><td colspan="6" class="text-center">Now</td></tr>
                  <tr *ngIf="i && jobs[i - 1].isFuture && !job.isFuture"><td colspan="6" class="text-center">Now</td></tr>
                  <tr job-list-row [viewButton]="!job.isFuture" [job]="job"></tr>
              </ng-container>
              </tbody>
          </table>
      </div>
  `
})

export class TimelineComponent implements OnInit {
  @Input() jobTemplateId: string;
  @Input() limit: number;

  jobs: Job[];

    constructor(private timelineService: TimelineService, private wamp: WampService) { }

    ngOnInit(): void {
      this.refreshGrid();

      this.wamp.topic('comrade.job_updated')
          .map((event: EventMessage) => event.args.pop())
          .filter((job: Job) => {
            if (this.jobTemplateId && job.templateId !== this.jobTemplateId) {
                return false;
            }

            return true;
          })
          .subscribe((job: Job) => {
              job.isFuture = job.createdAt.unix > (Date.now() / 1000);

              this.jobs = this.addJob(this.jobs, job);
          });

        Observable.interval(1000).subscribe(() => {
            let BreakException = {};

            try {
                this.jobs.forEach((eachJob: Job) => {
                    if (eachJob.isFuture && eachJob.createdAt.unix < (Date.now() / 1000)) {
                        throw BreakException;
                    }
                });
            } catch (e) {
                if (e === BreakException) {
                    this.jobs = this.jobs.filter((eachJob: Job) => !eachJob.isFuture);
                    this.refreshFuture();
                } else {
                    throw e;
                }
            }
        });
    }

    private refreshGrid():void
    {
        this.jobs = [];

        this.timelineService.getTimelineDone(new GetTimeline(this.jobTemplateId, this.limit))
            .subscribe(jobs => {
                this.jobs = [...this.jobs, ...jobs.map((eachJob: Job) => {
                    eachJob.isFuture = false;

                    return eachJob;
                })];

                this.jobs = this.sortJobs(this.jobs);
            });

        this.refreshFuture();
    }

    private refreshFuture():void
    {
        this.timelineService.getTimelineFuture(new GetTimeline(this.jobTemplateId, this.limit))
            .subscribe(jobs => {
                this.jobs = [...this.jobs, ...jobs.map((eachJob: Job) => {
                    eachJob.isFuture = true;

                    return eachJob;
                })];

                this.jobs = this.sortJobs(this.jobs);
            });
    }

    private addJob(jobs: Job[], newJob: Job): Job[] {
        return this.sortJobs([
            ...jobs.filter((eachJob: Job) => eachJob.id !== newJob.id),
            newJob
        ]);
    }

    private sortJobs(jobs: Job[]): Job[] {
        return jobs.sort(function(left, right) {
            if (left.createdAt.unix > right.createdAt.unix) {
                return -1;
            }
            if (left.createdAt.unix < right.createdAt.unix) {
                return 1;
            }

            return 0;
        });
    }
}
