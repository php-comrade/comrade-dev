import {Component, Input} from '@angular/core';
import {Date} from "../shared/date";

@Component({
  selector: 'time-cal',
  template: `
      <time [attr.datetime]="date.unix" [attr.title]="date.iso">
          {{ (date.unix | amFromUnix) | amCalendar }}
      </time>
  `,
})
export class TimeCalComponent {
    @Input() date: Date;
}
