import {Component, EventEmitter, Output} from '@angular/core';
import {RetryFailedPolicy} from "../shared/retry-failed-policy";

@Component({
  selector: 'new-retry-failed-policy',
  template: `
      <h6>New retry failed policy:</h6>
      <div class="form-group">
          <label for="on-duplicate-run">Retry Limit:</label>
          <input type="number" class="form-control" min="1" id="retry-limit" required [(ngModel)]="policy.retryLimit" name="retry-limit">
      </div>

      <a href="javascript:void(0)" (click)="add()">Add</a>
  `,
})
export class NewRetryFailedPolicyComponent {
    @Output() onPolicyAdded = new EventEmitter<RetryFailedPolicy>();

    policy: RetryFailedPolicy;

    constructor() {
        this.policy = new RetryFailedPolicy();
        this.policy.retryLimit = 1;
    }

    add() {
        this.onPolicyAdded.emit(this.policy);
    }
}
