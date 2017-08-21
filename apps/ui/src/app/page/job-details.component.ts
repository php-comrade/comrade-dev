import { Component, OnInit } from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";
import {ActivatedRoute, Params} from "@angular/router";
import 'rxjs/add/operator/switchMap';

@Component({
  selector: 'job-details',
  templateUrl: './job-details.component.html',
})
export class JobDetailsComponent implements OnInit {
  jobTemplate: JobTemplate;
  error: Error;

  tab: string = 'summary';

  constructor(
      private jobTemplateService: JobTemplateService,
      private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.params
        .switchMap((params: Params) => this.jobTemplateService.getJobTemplate(params['id']))
        .subscribe(jobTemplate => {
          this.jobTemplate = jobTemplate;
        });
  }

  switchTab(newTab: string, event):void {
    event.stopPropagation();

    this.tab = newTab;
  }

  onRunFailed(error: Error):void {
    this.error = error;
  }

  onRunSucceeded(jobTemplate: JobTemplate):void {
    this.jobTemplate = jobTemplate;
  }

  // TODO: Remove this when we're done
  get diagnostic() { return JSON.stringify(this.jobTemplate); }
}
