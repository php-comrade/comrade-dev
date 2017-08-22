import { Component, OnInit } from '@angular/core';
import {ActivatedRoute, Params} from "@angular/router";
import 'rxjs/add/operator/switchMap';
import {JobService} from "../shared/job.service";
import {Job} from "../shared/job";
import {GetJob} from "../shared/messages/get-job";

@Component({
  selector: 'job-view',
  templateUrl: './job-view.component.html',
})
export class JobViewComponent implements OnInit {
  job: Job;
  error: Error;

  tab: string = 'summary';

  constructor(
      private jobService: JobService,
      private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.params
        .switchMap((params: Params) => this.jobService.getJob(new GetJob(params['id'])))
        .subscribe((job: Job) => this.job = job);
  }

  switchTab(newTab: string, event):void {
    event.stopPropagation();

    this.tab = newTab;
  }
}
