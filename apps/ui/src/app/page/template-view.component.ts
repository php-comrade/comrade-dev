import { Component, OnInit } from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {ActivatedRoute, Params} from "@angular/router";
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/do';
import {CurrentJobTemplateService} from "../shared/current-job-template.service";

@Component({
  selector: 'template-view',
  templateUrl: './template-view.component.html',
})
export class TemplateViewComponent implements OnInit {
  jobTemplate: JobTemplate;
  error: Error;

  tab: string = 'summary';

  constructor(
      private route: ActivatedRoute,
      private currentJobTemplateService: CurrentJobTemplateService,
  ) { }

  ngOnInit(): void {
      this.route.params
          .do((params: Params) => this.tab = params['tab'])
          .switchMap((params: Params) => {
              this.currentJobTemplateService.change(params['id']);

              return this.currentJobTemplateService.getCurrentJobTemplate();
          })
          .subscribe((jobTemplate: JobTemplate) => {
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
