import {Component} from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import {JobTemplateService} from "../shared/job-template.service";
import * as uuid from "uuid";
import {Router} from "@angular/router";
import 'rxjs/add/operator/catch';
import {Trigger} from "../shared/trigger";
import {CronTrigger} from "../shared/cron-trigger";
import {SimpleTrigger} from "../shared/simple-trigger";

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent {
  jobTemplate: JobTemplate;

  submitted: boolean;

  message: string;

  addCronTrigger: boolean = false;

  addSimpleTrigger: boolean = false;

  constructor(private jobTemplateService: JobTemplateService, private router: Router) {
    this.jobTemplate = new JobTemplate();
    this.jobTemplate.templateId = uuid.v4();
    this.jobTemplate.processTemplateId = uuid.v4();
    this.submitted = false;
    this.message = '';
  }

  onFormChange(): void {
     this.message = '';
  }

  onSubmit() {
    this.submitted = true;

    this.jobTemplateService.create(this.jobTemplate)
        .catch(res => { throw res })
        .subscribe(
            res => this.router.navigate(['job-template', this.jobTemplate.templateId]),
            err => this.message = err
        );

    this.submitted = false;
  }

  triggerCronTrigger(): void {
      this.addCronTrigger = !this.addCronTrigger;
  }

  triggerSimpleTrigger(): void {
      this.addSimpleTrigger = !this.addSimpleTrigger;
  }

  onTriggerAdded(trigger: Trigger) {
    this.jobTemplate.addTrigger(trigger);

    if (trigger instanceof CronTrigger) {
      this.addCronTrigger = false;
    }
    if (trigger instanceof SimpleTrigger) {
        this.addSimpleTrigger = false;
    }
  }

  onRemoveTrigger(trigger: Trigger)
  {
    this.jobTemplate.removeTrigger(trigger);
  }

  // TODO: Remove this when we're done
  get diagnostic() { return JSON.stringify(this.jobTemplate); }
}