import { Component, OnInit } from '@angular/core';
import {ActivatedRoute, Params} from "@angular/router";
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/filter';
import {JobService} from "../shared/job.service";
import {Job} from "../shared/job";
import {GetJob} from "../shared/messages/get-job";
import {GetSubJobs} from "../shared/messages/get-sub-jobs";

@Component({
  selector: 'job-view',
  templateUrl: './job-view.component.html',
})
export class JobViewComponent implements OnInit {
  error: Error;

  job: Job;
  subJobs: Job[] = [];

  tab: string = 'summary';

  constructor(
      private jobService: JobService,
      private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
      this.route.params
          .do((params: Params) => this.tab = params['tab'] || 'summary')
          .switchMap((params: Params) => this.jobService.getJob(new GetJob(params['id'])))
          .subscribe((job: Job) => {
              this.job = job;

              if (job.runSubJobsPolicy) {
                  this.route.params
                      .filter((params: Params) => params['tab'] == 'sub-jobs')
                      .switchMap((params: Params) => this.jobService.getSubJobs(new GetSubJobs(params['id'])))
                      .subscribe((jobs: Job[]) => this.subJobs = jobs);
              }
          });
  }
}
