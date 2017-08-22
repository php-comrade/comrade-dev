import { Component, OnInit } from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";
import {ActivatedRoute, Params} from "@angular/router";
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/do';

@Component({
  selector: 'template-view',
  templateUrl: './template-view.component.html',
})
export class TemplateViewComponent implements OnInit {
  jobTemplate: JobTemplate;
  error: Error;

  tab: string = 'summary';

  constructor(
      private jobTemplateService: JobTemplateService,
      private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.params
        .do((params: Params) => this.tab = params['tab'] || 'summary')
        .switchMap((params: Params) => this.jobTemplateService.getJobTemplate(params['id']))

        .subscribe(jobTemplate => {
          this.jobTemplate = jobTemplate;
        });
  }

  onRunFailed(error: Error):void {
    this.error = error;
  }

  onRunSucceeded(jobTemplate: JobTemplate):void {
    this.jobTemplate = jobTemplate;
  }
}
