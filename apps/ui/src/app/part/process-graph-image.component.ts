import {Component, Input} from '@angular/core';

@Component({
  selector: 'process-graph-image',
  template: `<img src="http://jm.loc/process/{{ processId }}/graph.png" />`,
})
export class ProcessGraphImageComponent {
    @Input() processId: string;
}
