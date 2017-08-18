import { Component, OnInit } from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";
import {ActivatedRoute, Params} from "@angular/router";
import 'rxjs/add/operator/switchMap';
import {Job} from "../shared/job";
import {JobService} from "../shared/job.service";

@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class DetailsComponent implements OnInit {
  jobTemplate: JobTemplate;
  jobs: Job[];
  error: Error;

  tab: string = 'summary';

  constructor(
      private jobService: JobService,
      private jobTemplateService: JobTemplateService,
      private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.params
        .switchMap((params: Params) => this.jobTemplateService.getJobTemplate(params['id']))
        .subscribe(jobTemplate => {
          this.jobTemplate = jobTemplate;
          this.jobService.getJobs(this.jobTemplate).then(jobs => {
              this.jobs = jobs;
          });
        });
  }

  switchTab(newTab: string, event):void {
    event.stopPropagation();

    this.tab = newTab;
  }

  onRunFailed(error: Error):void {
    this.error = error;
  }

  // TODO: Remove this when we're done
  get diagnostic() { return JSON.stringify(this.jobTemplate); }
}
