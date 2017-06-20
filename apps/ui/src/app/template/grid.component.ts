import { Component } from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";

@Component({
  selector: 'template-grid',
  templateUrl: './grid.component.html',
  styleUrls: ['./grid.component.css']
})

export class GridComponent {
  jobTemplates: JobTemplate[];

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
}
