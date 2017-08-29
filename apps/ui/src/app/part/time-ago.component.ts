import {Component, Input} from '@angular/core';
import {Date} from "../shared/date";

@Component({
  selector: 'time-ago',
  template: `
      <time [attr.datetime]="date.unix" [attr.title]="date.iso">
          {{ (date.unix | amFromUnix) | amTimeAgo }}
      </time>
  `,
})
export class TimeAgoComponent {
    @Input() date: Date;
}
