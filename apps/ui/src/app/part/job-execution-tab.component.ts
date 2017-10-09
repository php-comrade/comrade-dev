import {Component, Input, OnChanges, SimpleChanges} from '@angular/core';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/filter';
import {Job} from "../shared/job";
import {JobResultMetrics} from "../shared/job-result-metrics";
import {JobResultError} from "../shared/job-result-error";
import {JobResult as OriginalJobResult} from "../shared/job-result";
import {SubJob} from "../shared/sub-job";
import {JobStatus} from "../shared/job-status";

class JobResult extends OriginalJobResult {
  isSubJob: boolean;
}

@Component({
  selector: 'job-execution-tab',
  template: `
      <div class="clearfix mt-2"></div>
      
      <div class="row pb-2">
          <div class="col-6">
              <div *ngIf="job.runSubJobsPolicy" class="row pb-2">
                  <div class="col-4" *ngIf="!index">
                      <label for="showSubJobsToggler">Show sub jobs</label>
                  </div>
                  <div class="col-2" *ngIf="!index">
                      <input
                              type="checkbox"
                              id="showSubJobsToggler"
                              [checked]="showSubJobsStatuses"
                              (change)="triggerShowSubJobs()"
                      >
                  </div>
              </div>
              <div class="row pb-2" *ngFor="let result of results; let index = index">
                  <div class="col-4" *ngIf="!index">
                      <time-ago [date]="result.createdAt"></time-ago>
                  </div>
                  <div class="col-4" *ngIf="index">
                      &nbsp;&nbsp;+{{ result.createdAt.unix - job.results[0].createdAt.unix }} sec
                  </div>

                  <div class="col-4">
                      <span class="float-left" *ngIf="result.isSubJob">&nbsp;&nbsp;</span>
                      <job-status class="float-left" [status]="result.status"></job-status>
                  </div>
              </div>
          </div>
          <div class="col-6">
              <job-state-graph [jobId]="job.id" [updatedAt]="job.updatedAt"></job-state-graph>
          </div>
      </div>

      <hr class="clearfix mt-2" />

      <div class="row pb-2">
          <div class="col-6">
              <div class="row pb-2" *ngFor="let result of results; let index = index">
                  <div class="col-4" *ngIf="!index">
                      <time-ago [date]="result.createdAt"></time-ago>
                  </div>
                  <div class="col-4" *ngIf="index">
                      &nbsp;&nbsp;+{{ result.createdAt.unix - job.results[0].createdAt.unix }} sec
                  </div>
                  
                  <div class="col-4">
                      <span class="float-left" *ngIf="result.isSubJob">&nbsp;&nbsp;</span>
                      <job-status class="float-left" [status]="result.status"></job-status>
                  </div>
                  <div class="col-2" *ngIf="result.error">
                  <a (click)="showError(result.error)" href="javascript:void(0)">error</a>
                  </div>

                  <div class="col-2" *ngIf="result.metrics">
                  <a (click)="showMetrics(result.metrics)" href="javascript:void(0)">metrics</a>
                  </div>
              </div>
          </div>
          <div class="col-6">
              <div class="col-12" *ngIf="metrics">
              <prettyjson [obj]="metrics"></prettyjson>
              </div>
              <div class="col-12" *ngIf="error">
              <prettyjson [obj]="error"></prettyjson>
              </div>
          </div>
      </div>
  `,
})
export class JobExecutionTabComponent implements OnChanges {
  @Input() job: Job;
  @Input() subJobs: Job[];

  metrics: JobResultMetrics;
  error: JobResultError;

  results: JobResult[];

  showSubJobsStatuses: boolean = true;

  ngOnChanges(changes: SimpleChanges): void {
    if (changes.job) {
      this.job = {...changes.job.currentValue};
      this.job.results = [...this.job.results];
    }

    if (changes.subJobs) {
      this.subJobs = [...changes.subJobs.currentValue];

      this.subJobs.forEach((job: SubJob) => {
        if (job.currentResult) {
          job.currentResult = {...job.currentResult};
        }
      });
    }

    this.refreshResults();
  }

  showMetrics(metrics: JobResultMetrics): void {
    this.error = null;
    this.metrics = metrics;
  }

  showError(error: JobResultError): void {
    this.metrics = null;
    this.error = error;
  }

  refreshResults():void {
    const results: JobResult[] = [];

    // for some reason the code stops working if this commented out. at least in chrome.
    this.job.results.forEach((jobResult: JobResult) => {
      jobResult.isSubJob = false;
      results.push(jobResult);

      if (this.showSubJobsStatuses && jobResult.status == JobStatus.RUNNING_SUB_JOBS && this.subJobs) {
        this.subJobs.forEach((job: SubJob) => {
          if (!job.currentResult) {
            return;
          }

          const result = job.currentResult as JobResult;
          result.isSubJob = true;

          results.push(result);
        });
      }
    });

    this.results = results;
  }

  triggerShowSubJobs():void {
    this.showSubJobsStatuses = !this.showSubJobsStatuses;

    this.refreshResults();
  }
}
