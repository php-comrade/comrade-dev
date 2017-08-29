import {Component, EventEmitter, Output} from '@angular/core';
import {GracePeriodPolicy} from "../shared/grace-period-policy";

@Component({
  selector: 'new-grace-period-policy',
  template: `
      <h6>New grace period policy:</h6>
      <div class="form-group">
          <label for="on-duplicate-run">Period (sec):</label>
          <input type="number" class="form-control" min="1" id="period" required [(ngModel)]="policy.period" name="period">
      </div>

      <a href="javascript:void(0)" (click)="add()">Add</a>
  `,
})
export class NewGracePeriodPolicyComponent {
    @Output() onPolicyAdded = new EventEmitter<GracePeriodPolicy>();

    policy: GracePeriodPolicy;

    constructor() {
        this.policy = new GracePeriodPolicy();
        this.policy.period = 180;
    }

    add() {
        this.onPolicyAdded.emit(this.policy);
    }
}
