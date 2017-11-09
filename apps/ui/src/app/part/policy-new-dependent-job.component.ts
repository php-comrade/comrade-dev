import {Component, EventEmitter, Output} from '@angular/core';
import {RunDependentJobPolicy} from "../shared/run-dependent-job-policy";
import {JobStatus} from "../shared/job-status";
import {JobTemplate} from "../shared/job-template";

class SelectOption {
  constructor(public id: string, public text: string) {}
}

@Component({
  selector: 'policy-new-run-dependent-job',
  template: `
      <h6>New dependent job policy:</h6>
      <div class="form-group">
          <label for="sub-job-policy-parent-id">Template Id:</label>
          <template-search (onUnSelected)="unselected()" (onSelected)="selected($event)"></template-search>
      </div>
      <div class="form-group">
          <label for="sub-job-policy-parent-id">Run always:</label>
          <input type="checkbox" class="form-control" id="run-dependent-job-policy-run-always" required [(ngModel)]="policy.runAlways">
      </div>

      <div *ngIf="!policy.runAlways" class="form-group">
          <label for="sub-job-policy-parent-id">On status:</label>

          <span
              [ngClass]="{'badge-default': !policy.runOnStatus.has(opt.id), 'badge-success': policy.runOnStatus.has(opt.id) }" 
              *ngFor="let opt of statusOptions" 
              class="w-20 ml-1 text-center badge badge-default" 
              (click)="toggleStatus(opt.id)"
          >
              {{ opt.text }}
          </span>
      </div>

      <a href="javascript:void(0)" (click)="add()">Add</a>
  `,
})
export class PolicyNewRunDependentJobComponent {
    @Output() onPolicyAdded = new EventEmitter<RunDependentJobPolicy>();

    policy: RunDependentJobPolicy;

    statusOptions: SelectOption[] = [
        new SelectOption(JobStatus.CANCELED, 'Canceled'),
        new SelectOption(JobStatus.COMPLETED, 'Completed'),
        new SelectOption(JobStatus.FAILED, 'Failed'),
        new SelectOption(JobStatus.TERMINATED, 'Terminated'),
    ];

    constructor() {
        this.policy = new RunDependentJobPolicy();
    }

    add() {
        this.onPolicyAdded.emit(this.policy);
    }

    public toggleStatus(status: string): void {
        if (this.policy.runOnStatus.has(status)) {
          this.policy.runOnStatus.delete(status);
        } else {
          this.policy.runOnStatus.add(status);
        }
    }

    public unselected(): void {
      this.policy.templateId = null;
    }

    public selected(template: JobTemplate): void
    {
        this.policy.templateId = template.templateId;
    }
}
