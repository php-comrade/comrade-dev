import {Component, OnInit} from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";

@Component({
  selector: 'job-grid',
  templateUrl: './job-grid.component.html',
})

export class JobGridComponent implements OnInit {
  jobTemplates: JobTemplate[];
  error: Error;

  constructor(
      private jobTemplateService: JobTemplateService
  ) { }

  ngOnInit(): void {
    this.getJobTemplates();
  }

  getJobTemplates(): void {
    this.jobTemplateService.getJobTemplates().then(jobTemplates => {
      this.jobTemplates = jobTemplates
    });
  }

  onRunFailed(error: Error):void {
      this.error = error;
  }
}
