import {Component, EventEmitter, Input, Output} from '@angular/core';
import {RunSubJobsPolicy} from "../shared/run-sub-jobs-policy";

@Component({
  selector: 'show-run-sub-jobs-policy',
  template: `
      <p>
          Job is allowed to run sub jobs. If sub job fails the parent job is {{ policy.onFailedSubJob }}
          <a (click)="remove(policy)" href="javascript:void(0)">Remove</a>
      </p>
  `,
})
export class ShowRunSubJobsPolicyComponent {
    @Input() policy: RunSubJobsPolicy;
    @Output() onRemovePolicy = new EventEmitter<RunSubJobsPolicy>();

    remove(policy: RunSubJobsPolicy) {
        this.onRemovePolicy.emit(policy);
    }
}
