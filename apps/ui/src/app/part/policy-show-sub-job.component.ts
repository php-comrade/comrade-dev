import {Component, EventEmitter, Input, Output} from '@angular/core';
import {RunSubJobsPolicy} from "../shared/run-sub-jobs-policy";
import {SubJobPolicy} from "../shared/sub-job-policy";

@Component({
  selector: 'policy-show-sub-job',
  template: `
      <p>
          Job is allowed to be run as a sub job of <a [routerLink]="['/template', policy.parentId, 'view' ]">{{policy.parentId}}</a>
          <a (click)="remove(policy)" href="javascript:void(0)">Remove</a>
      </p>
  `,
})
export class PolicyShowSubJobComponent {
    @Input() policy: SubJobPolicy;
    @Output() onRemovePolicy = new EventEmitter<SubJobPolicy>();

    remove(policy: SubJobPolicy) {
        this.onRemovePolicy.emit(policy);
    }
}
