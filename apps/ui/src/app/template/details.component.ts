import { Component, OnInit } from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";
import {ActivatedRoute, Params} from "@angular/router";
import 'rxjs/add/operator/switchMap';
import {Job} from "../shared/job";
import {TimelineService} from "../shared/timeline.service";
import {GetTimeline} from "../shared/messages/get-timeline";

@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class DetailsComponent implements OnInit {
  jobTemplate: JobTemplate;
  doneJobs: Job[];
  futureJobs: Job[];
  error: Error;

  tab: string = 'summary';

  constructor(
      private jobTemplateService: JobTemplateService,
      private timelineService: TimelineService,
      private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.params
        .switchMap((params: Params) => this.jobTemplateService.getJobTemplate(params['id']))
        .subscribe(jobTemplate => {
          this.jobTemplate = jobTemplate;
          this.timelineService.getTimelineDone(new GetTimeline(this.jobTemplate.templateId))
            .subscribe(jobs => {
                this.doneJobs = jobs;
            });
          this.timelineService.getTimelineFuture(new GetTimeline(this.jobTemplate.templateId))
              .subscribe(jobs => {
               this.futureJobs = jobs;
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

  onRunSucceeded(jobTemplate: JobTemplate):void {
    this.jobTemplate = jobTemplate;
  }

  // TODO: Remove this when we're done
  get diagnostic() { return JSON.stringify(this.jobTemplate); }
}
