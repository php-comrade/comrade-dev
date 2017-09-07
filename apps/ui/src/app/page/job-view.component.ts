import { Component, OnInit } from '@angular/core';
import {ActivatedRoute, Params} from "@angular/router";
import { Title }     from '@angular/platform-browser';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/filter';
import {JobService} from "../shared/job.service";
import {Job} from "../shared/job";
import {GetSubJobs} from "../shared/messages/get-sub-jobs";
import {CurrentJobService} from "../shared/current-job.service";

@Component({
  selector: 'job-view',
  templateUrl: './job-view.component.html',
})
export class JobViewComponent implements OnInit {
  error: Error;

  job: Job;
  subJobs: Job[] = [];

  tab: string = 'summary';

  updatedAt: number;

  constructor(
      private currentJobService: CurrentJobService,
      private jobService: JobService,
      private route: ActivatedRoute,
      private titleService: Title,
  ) { }

  ngOnInit(): void {
      this.titleService.setTitle('JM. Job view');

      this.route.params
          .do((params: Params) => this.tab = params['tab'])
          .switchMap((params: Params) => {
            this.currentJobService.change(params['id']);

            return this.currentJobService.getCurrentJob();
          })
          .subscribe((job: Job) => {
              this.job = job;
              this.updatedAt = Date.now();

              if (job && job.runSubJobsPolicy) {
                  this.route.params
                      .filter((params: Params) => params['tab'] == 'sub-jobs')
                      .switchMap((params: Params) => this.jobService.getSubJobs(new GetSubJobs(params['id'])))
                      .subscribe((jobs: Job[]) => this.subJobs = jobs);
              }
          });
  }
}
