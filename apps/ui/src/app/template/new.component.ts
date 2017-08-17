import {Component} from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";
import * as uuid from "uuid";
import {Router} from "@angular/router";
import 'rxjs/add/operator/catch';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent {
  model: JobTemplate;

  submitted: boolean;

  message: string;

  constructor(private jobTemplateService: JobTemplateService, private router: Router) {
    this.model = new JobTemplate();
    this.model.templateId = uuid.v4();
    this.model.processTemplateId = uuid.v4();
    this.submitted = false;
    this.message = '';
  }

  onFormChange(): void {
     this.message = '';
  }

  onSubmit() {
    this.submitted = true;

    this.jobTemplateService.create(this.model)
        .catch(res => { throw res })
        .subscribe(
            res => this.router.navigate(['job-template', this.model.templateId]),
            err => this.message = err
        );

    this.submitted = false;
  }

  // TODO: Remove this when we're done
  get diagnostic() { return JSON.stringify(this.model); }
}
