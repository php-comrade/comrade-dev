import {Component, EventEmitter, Input, Output} from '@angular/core';
import {RunDependentJobPolicy} from "../shared/run-dependent-job-policy";

@Component({
  selector: 'policy-show-run-dependent-job',
  template: `
      <p>
          Job executes a dependent job 
          <a [routerLink]="['/template', policy.templateId, 'view' ]">{{policy.templateId}}</a> 
          <span *ngIf="policy.runAlways">always</span>
          <span *ngIf="!policy.runAlways">on status {{ getStatusesAsString() }}</span> 
          <a (click)="remove(policy)" href="javascript:void(0)">Remove</a>
      </p>
  `,
})
export class PolicyShowRunDependentJobComponent {
    @Input() policy: RunDependentJobPolicy;
    @Output() onRemovePolicy = new EventEmitter<RunDependentJobPolicy>();

    remove(policy: RunDependentJobPolicy) {
        this.onRemovePolicy.emit(policy);
    }

    getStatusesAsString(): string
    {
        return Array.from(this.policy.runOnStatus).join(', ');
    }
}
