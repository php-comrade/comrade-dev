import { Component, OnInit } from '@angular/core';
import { Response } from '@angular/http';
import {ActivatedRoute, Params} from "@angular/router";
import { Title }     from '@angular/platform-browser';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/filter';
import 'rxjs/add/operator/map';
import {Job} from "../shared/job";
import {CurrentJobService} from "../shared/current-job.service";
import {CurrentSubJobsService} from "../shared/current-sub-jobs.service";
import {SubJob} from "../shared/sub-job";
import {HttpService} from "../shared/http.service";
import {GetDependentJobs} from "../shared/messages/get-dependent-jobs";
import {GetDependentJobsResult} from "../shared/messages/get-dependent-jobs-result";

@Component({
  selector: 'job-view',
  templateUrl: './job-view.component.html',
})
export class JobViewComponent implements OnInit {
  error: Error;

  job: Job;
  subJobs: Job[] = [];

  dependentJobs: Job[] = [];

  tab: string = 'summary';

  constructor(
      private currentJobService: CurrentJobService,
      private route: ActivatedRoute,
      private titleService: Title,
      private currentSubJobsService: CurrentSubJobsService,
      private httpService: HttpService
  ) { }

  ngOnInit(): void {
      this.titleService.setTitle('Comrade - Job view');

      let jobStream = this.route.params
          .do((params: Params) => this.tab = params['tab'])
          .switchMap((params: Params) => {
            this.currentJobService.change(params['id']);

            return this.currentJobService.getCurrentJob();
          })
      ;

      jobStream.subscribe((job: Job) => this.job = job);
      jobStream.subscribe((job: Job) => {
          if (job && job.runDependentJobPolicies && job.runDependentJobPolicies.length) {
            this.httpService.post('/api/get-dependent-jobs', new GetDependentJobs(job.id))
              .map((res: Response) => res.json() as GetDependentJobsResult)
              .subscribe((result: GetDependentJobsResult) => {
                  this.dependentJobs = result.jobs;
              })
          }
      });

      this.currentSubJobsService.getCurrentSubJobs().subscribe((jobs: SubJob[]) => {
        this.subJobs = jobs;
      });
  }
}
