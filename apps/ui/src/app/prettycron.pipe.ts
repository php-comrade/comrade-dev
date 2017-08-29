import { Pipe, PipeTransform } from '@angular/core';

@Pipe({name: 'prettycron'})
export class PrettycronPipe implements PipeTransform {
    transform(value: string): string {
        require('later/later.js');

        return require('prettycron').toString(value);
    }
}