import {Component, EventEmitter, Input, Output} from '@angular/core';
import { JobTemplate } from "../shared/job-template";
import { JobTemplateService } from "../shared/job-template.service";

@Component({
  selector: 'job-run-now',
  template: `
<button *ngIf="!jobTemplate.subJobPolicy || !jobTemplate.runner"
  type="button" 
  class="btn btn-primary btn-sm" 
  (click)="runNow(jobTemplate, $event)"
>
    Run now
</button>`,
})
export class RunNowJobComponent {
    @Input() jobTemplate: JobTemplate;
    @Output() onRunFailed = new EventEmitter<Error>();
    @Output() onRunSucceeded = new EventEmitter<JobTemplate>();

    constructor(private jobTemplateService: JobTemplateService) { }

    runNow(jobTemplate: JobTemplate, event):void {
        event.stopPropagation();

        this.jobTemplateService.runNow(jobTemplate).subscribe({
          error: (error: Error) => this.onRunFailed.emit(error)
        });
    }
}
