import { Component, OnInit } from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";
import {ActivatedRoute, Params} from "@angular/router";
import 'rxjs/add/operator/switchMap';

@Component({
  selector: 'app-details',
  templateUrl: './details.component.html',
  styleUrls: ['./details.component.css']
})
export class DetailsComponent implements OnInit {
  jobTemplate: JobTemplate;
  tab: string = 'summary';

  constructor(
      private jobTemplateService: JobTemplateService,
      private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.params
        .switchMap((params: Params) => this.jobTemplateService.getJobTemplate(params['id']))
        .subscribe(jobTemplate => this.jobTemplate = jobTemplate);
  }

  switchTab(newTab: string, event):void {
    event.stopPropagation();

    this.tab = newTab;
  }

  // TODO: Remove this when we're done
  get diagnostic() { return JSON.stringify(this.jobTemplate); }
}
