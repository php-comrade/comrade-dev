import {Component, EventEmitter, Input, Output} from '@angular/core';
import {GracePeriodPolicy} from "../shared/grace-period-policy";

@Component({
  selector: 'show-grace-period-policy',
  template: `
      <p>
          Job will be marked as failed if not finished within {{ policy.period }} seconds
          <a (click)="remove(policy)" href="javascript:void(0)">Remove</a>
      </p>
  `,
})
export class ShowGracePeriodPolicyComponent {
    @Input() policy: GracePeriodPolicy;
    @Output() onRemovePolicy = new EventEmitter<GracePeriodPolicy>();

    remove(policy: GracePeriodPolicy) {
        this.onRemovePolicy.emit(policy);
    }
}
