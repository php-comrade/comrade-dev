import {Component, EventEmitter, Input, Output} from '@angular/core';
import {RetryFailedPolicy} from "../shared/retry-failed-policy";

@Component({
  selector: 'show-retry-failed-policy',
  template: `
      <p>
          Job will be re-run {{ policy.retryLimit }} times on fail before it is finally marked as failed. 
          <a (click)="remove(policy)" href="javascript:void(0)">Remove</a>
      </p>
  `,
})
export class ShowRetryFailedPolicyComponent {
    @Input() policy: RetryFailedPolicy;
    @Output() onRemovePolicy = new EventEmitter<RetryFailedPolicy>();

    remove(policy: RetryFailedPolicy) {
        this.onRemovePolicy.emit(policy);
    }
}
