import {Component, EventEmitter, Input, Output} from '@angular/core';
import { JobTemplate } from "../shared/job-template";
import { JobTemplateService } from "../shared/job-template.service";

@Component({
  selector: 'job-run-now',
  template: `
<button 
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

    constructor(private jobTemplateService: JobTemplateService) { }

    runNow(jobTemplate: JobTemplate, event):void {
        event.stopPropagation();

        this.jobTemplateService.runNow(jobTemplate)
            .catch(res => { throw res })
            .subscribe({
                error: err => {
                    this.onRunFailed.emit(err);
                }
            });
    }
}