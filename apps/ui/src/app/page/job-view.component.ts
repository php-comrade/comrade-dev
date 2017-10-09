import { Component, OnInit } from '@angular/core';
import {ActivatedRoute, Params} from "@angular/router";
import { Title }     from '@angular/platform-browser';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/filter';
import {Job} from "../shared/job";
import {CurrentJobService} from "../shared/current-job.service";
import {CurrentSubJobsService} from "../shared/current-sub-jobs.service";
import {SubJob} from "../shared/sub-job";

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
      private currentJobService: CurrentJobService,
      private route: ActivatedRoute,
      private titleService: Title,
      private currentSubJobsService: CurrentSubJobsService
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
          });

      this.currentSubJobsService.getCurrentSubJobs().subscribe((jobs: SubJob[]) => {
        this.subJobs = jobs;
      });
  }
}
