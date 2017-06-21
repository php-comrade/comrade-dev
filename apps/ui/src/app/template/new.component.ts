import {Component, OnInit} from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";
import * as uuid from "uuid";

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent {
  model: JobTemplate;

  submitted: boolean;

  constructor(private jobTemplateService: JobTemplateService) {
    this.model = new JobTemplate();
    this.model.templateId = uuid.v4();
    this.model.processTemplateId = uuid.v4();
    this.submitted = false;
  }

  onSubmit() {
    this.submitted = true;

    this.jobTemplateService.create(this.model);

    this.submitted = false;
  }

  // TODO: Remove this when we're done
  get diagnostic() { return JSON.stringify(this.model); }
}
